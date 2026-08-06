import 'package:flutter/material.dart';

import 'constant.dart';

class ParcelCard extends StatefulWidget {
  const ParcelCard({
    Key? key,
    required this.onTap,
    required this.name,
    required this.date,
    required this.deliveryTime,
    required this.pickupTime,
    required this.image,
  }) : super(key: key);
   final Function onTap;
  final String name;
  final String date;
  final String deliveryTime;
  final String pickupTime;
  final String image;

  @override
  State<ParcelCard> createState() => _ParcelCardState();
}

class _ParcelCardState extends State<ParcelCard> {
  @override
  Widget build(BuildContext context) {
    return
      InkWell(
        onTap: widget.onTap(),
        child: Padding(
        padding: const EdgeInsets.only(bottom: 14.0),
        child: Container(
          height: 96,
          width: double.infinity,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(width: 1, color:kMainColor),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    margin:
                        const EdgeInsets.symmetric(vertical: 18, horizontal: 14),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(50),
                      color: Colors.white,
                      image: DecorationImage(
                        image: AssetImage(widget.image),
                        fit: BoxFit.cover,
                      ),
                    ),
                    height: 60,
                    width: 60,
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 14.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          widget.name,
                          style: const TextStyle(
                            fontWeight: FontWeight.w500,
                            fontSize: 14,
                            color: nameColor,
                          ),
                        ),
                        Text(
                          widget.date,
                          style: const TextStyle(
                            fontWeight: FontWeight.w400,
                            fontSize: 12,
                            color: hintColor,
                          ),
                        ),
                        Text(
                          widget.deliveryTime,
                          style: const TextStyle(
                            fontWeight: FontWeight.w400,
                            fontSize: 12,
                            color: hintColor,
                          ),
                        ),
                        Text(
                          widget.pickupTime,
                          style: const TextStyle(
                            fontWeight: FontWeight.w400,
                            fontSize: 12,
                            color: hintColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

            ],
          ),
        ),
    ),
      );
  }
}
